import { useState } from "react";
import { Link } from "react-router-dom";

export default function Trending() {
  const [activeCategory, setActiveCategory] = useState("all");

  // Custom SVG Icons
  const TrendingIcon = () => (
    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
      <path fillRule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clipRule="evenodd" />
    </svg>
  );

  const FireIcon = () => (
    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
      <path fillRule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clipRule="evenodd" />
    </svg>
  );

  const EyeIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
    </svg>
  );

  const HeartIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
    </svg>
  );

  const ClockIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  );

  const TrendingUpIcon = () => (
    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
    </svg>
  );

  const trendingPosts = [
    {
      id: 1,
      title: "AI Revolution: How GPT-4 is Changing Web Development Forever",
      excerpt: "Discover how the latest AI models are transforming the way we build websites and applications, making development faster and more accessible.",
      author: "Sarah Chen",
      date: "2 hours ago",
      readTime: "8 min read",
      views: "15.2K",
      likes: "2.1K",
      growth: "+245%",
      category: "AI",
      hot: true,
      trend: "exploding"
    },
    {
      id: 2,
      title: "Next.js 14: The Game-Changer for React Developers",
      excerpt: "Explore the revolutionary features in Next.js 14 that are setting new standards for React development and performance.",
      author: "Mike Johnson",
      date: "5 hours ago",
      readTime: "12 min read",
      views: "12.8K",
      likes: "1.8K",
      growth: "+189%",
      category: "Next.js",
      hot: true,
      trend: "rising"
    },
    {
      id: 3,
      title: "The Dark Side of Microservices: When to Avoid Them",
      excerpt: "Learn about the pitfalls and challenges of microservices architecture and when a monolith might be a better choice.",
      author: "Alex Rodriguez",
      date: "8 hours ago",
      readTime: "15 min read",
      views: "10.5K",
      likes: "1.5K",
      growth: "+156%",
      category: "Architecture",
      hot: false,
      trend: "steady"
    },
    {
      id: 4,
      title: "CSS 2024: New Features You Can't Afford to Miss",
      excerpt: "Get ahead of the curve with these groundbreaking CSS features that are about to change web design forever.",
      author: "Emma Wilson",
      date: "1 day ago",
      readTime: "10 min read",
      views: "28.7K",
      likes: "3.2K",
      growth: "+312%",
      category: "CSS",
      hot: true,
      trend: "exploding"
    },
    {
      id: 5,
      title: "Web3 Development: Building Your First DApp",
      excerpt: "A beginner-friendly guide to creating decentralized applications on the blockchain with practical examples.",
      author: "David Kim",
      date: "1 day ago",
      readTime: "18 min read",
      views: "22.4K",
      likes: "2.8K",
      growth: "+278%",
      category: "Web3",
      hot: true,
      trend: "rising"
    },
    {
      id: 6,
      title: "The Death of Traditional Backends: Serverless is Here",
      excerpt: "Why more companies are moving to serverless architectures and what it means for backend developers.",
      author: "Lisa Wang",
      date: "2 days ago",
      readTime: "14 min read",
      views: "18.9K",
      likes: "2.3K",
      growth: "+201%",
      category: "Backend",
      hot: false,
      trend: "steady"
    },
    {
      id: 7,
      title: "TypeScript 5.3: Incredible New Features Released",
      excerpt: "Deep dive into the latest TypeScript release and how these features will improve your development workflow.",
      author: "Tom Harris",
      date: "2 days ago",
      readTime: "11 min read",
      views: "16.2K",
      likes: "1.9K",
      growth: "+167%",
      category: "TypeScript",
      hot: true,
      trend: "rising"
    },
    {
      id: 8,
      title: "The Ultimate Guide to Web Performance in 2024",
      excerpt: "Latest techniques and tools to make your website blazing fast and improve Core Web Vitals scores.",
      author: "Maria Garcia",
      date: "3 days ago",
      readTime: "20 min read",
      views: "35.1K",
      likes: "4.1K",
      growth: "+389%",
      category: "Performance",
      hot: true,
      trend: "exploding"
    }
  ];

  const categories = [
    { id: "all", name: "All Trends", count: 8 },
    { id: "AI", name: "AI", count: 1 },
    { id: "Next.js", name: "Next.js", count: 1 },
    { id: "CSS", name: "CSS", count: 1 },
    { id: "Web3", name: "Web3", count: 1 },
    { id: "TypeScript", name: "TypeScript", count: 1 },
    { id: "Performance", name: "Performance", count: 1 },
    { id: "Architecture", name: "Architecture", count: 1 },
    { id: "Backend", name: "Backend", count: 1 }
  ];

  const getTrendColor = (trend) => {
    switch (trend) {
      case "exploding": return "from-red-500 to-orange-500";
      case "rising": return "from-green-500 to-emerald-500";
      case "steady": return "from-blue-500 to-cyan-500";
      default: return "from-gray-500 to-gray-600";
    }
  };

  const getTrendLabel = (trend) => {
    switch (trend) {
      case "exploding": return "Exploding";
      case "rising": return "Rising Fast";
      case "steady": return "Steady Growth";
      default: return "Trending";
    }
  };

  const filteredPosts = activeCategory === "all" 
    ? trendingPosts 
    : trendingPosts.filter(post => post.category === activeCategory);

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50/50 to-red-50/30 pt-20">
      {/* Header */}
      <div className="bg-white/80 backdrop-blur-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="text-center">
            <div className="flex items-center justify-center space-x-3 mb-4">
              <div className="p-3 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl shadow-lg">
                <TrendingIcon />
              </div>
              <h1 className="text-4xl lg:text-5xl font-bold text-gray-900">
                Trending Now
              </h1>
            </div>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Catch up with what's hot and happening right now in the tech world. 
              These articles are gaining massive traction in real-time.
            </p>
          </div>
        </div>
      </div>

      {/* Categories */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex flex-wrap gap-2 justify-center">
          {categories.map((category) => (
            <button
              key={category.id}
              onClick={() => setActiveCategory(category.id)}
              className={`flex items-center space-x-2 px-4 py-3 rounded-xl font-medium transition-all duration-300 ${
                activeCategory === category.id
                  ? "bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg transform -translate-y-1"
                  : "bg-white text-gray-700 hover:bg-gray-50 shadow-sm hover:shadow-md"
              }`}
            >
              <span>{category.name}</span>
              <span className={`text-sm px-2 py-1 rounded-full ${
                activeCategory === category.id
                  ? "bg-white/20 text-white"
                  : "bg-gray-100 text-gray-600"
              }`}>
                {category.count}
              </span>
            </button>
          ))}
        </div>
      </div>

      {/* Trending Posts */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid lg:grid-cols-2 xl:grid-cols-3 gap-6">
          {filteredPosts.map((post, index) => (
            <Link
              key={post.id}
              to={`/post/${post.id}`}
              className="group bg-white rounded-2xl shadow-sm hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 hover:border-orange-200 relative"
            >
              {/* Hot Badge */}
              {post.hot && (
                <div className="absolute top-4 left-4 z-10">
                  <div className="flex items-center space-x-1 bg-gradient-to-r from-red-500 to-orange-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                    <FireIcon className="w-3 h-3" />
                    <span>HOT</span>
                  </div>
                </div>
              )}

              {/* Trend Indicator */}
              <div className="absolute top-4 right-4 z-10">
                <div className={`flex items-center space-x-1 bg-gradient-to-r ${getTrendColor(post.trend)} text-white px-3 py-1 rounded-full text-sm font-medium`}>
                  <TrendingUpIcon className="w-3 h-3" />
                  <span>{post.growth}</span>
                </div>
              </div>

              <div className="p-6">
                {/* Category & Trend */}
                <div className="flex items-center justify-between mb-4">
                  <span className="inline-flex items-center px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
                    {post.category}
                  </span>
                  <span className={`text-xs font-medium px-2 py-1 rounded-full ${
                    post.trend === "exploding" ? "bg-red-100 text-red-800" :
                    post.trend === "rising" ? "bg-green-100 text-green-800" :
                    "bg-blue-100 text-blue-800"
                  }`}>
                    {getTrendLabel(post.trend)}
                  </span>
                </div>

                <h2 className="text-xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors duration-300 mb-3 line-clamp-2">
                  {post.title}
                </h2>
                
                <p className="text-gray-600 mb-4 line-clamp-3">
                  {post.excerpt}
                </p>

                {/* Author & Time */}
                <div className="flex items-center justify-between text-sm text-gray-500 mb-4">
                  <div className="flex items-center space-x-4">
                    <span className="font-medium text-gray-900">{post.author}</span>
                    <span className="flex items-center space-x-1">
                      <ClockIcon />
                      <span>{post.date}</span>
                    </span>
                  </div>
                  <span className="text-gray-400">{post.readTime}</span>
                </div>

                {/* Engagement Stats */}
                <div className="flex items-center justify-between pt-4 border-t border-gray-100">
                  <div className="flex items-center space-x-4 text-sm text-gray-500">
                    <div className="flex items-center space-x-1">
                      <EyeIcon />
                      <span>{post.views}</span>
                    </div>
                    <div className="flex items-center space-x-1">
                      <HeartIcon />
                      <span>{post.likes}</span>
                    </div>
                  </div>
                  
                  {/* Growth Indicator */}
                  <div className={`flex items-center space-x-1 text-sm font-medium ${
                    post.growth.includes('+') ? 'text-green-600' : 'text-red-600'
                  }`}>
                    <TrendingUpIcon className="w-3 h-3" />
                    <span>{post.growth}</span>
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>

        {/* Real-time Stats */}
        <div className="mt-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl p-8 text-white">
          <div className="text-center mb-8">
            <h2 className="text-2xl font-bold mb-2">Live Trend Metrics</h2>
            <p className="text-orange-100">Updated every 5 minutes</p>
          </div>
          <div className="grid md:grid-cols-4 gap-8 text-center">
            <div>
              <div className="text-3xl font-bold mb-2">1.2M+</div>
              <div className="text-orange-100">Active Readers</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-2">45K+</div>
              <div className="text-orange-100">New Reads/Hour</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-2">12K+</div>
              <div className="text-orange-100">Shares Today</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-2">98%</div>
              <div className="text-orange-100">Positive Engagement</div>
            </div>
          </div>
        </div>

        {/* Newsletter CTA */}
        <div className="mt-12 bg-white rounded-2xl shadow-lg p-8 text-center">
          <div className="max-w-2xl mx-auto">
            <h2 className="text-2xl font-bold text-gray-900 mb-4">
              Never Miss a Trend
            </h2>
            <p className="text-gray-600 mb-6">
              Get daily updates on the hottest topics and trending articles delivered straight to your inbox.
            </p>
            <div className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
              <input
                type="email"
                placeholder="Enter your email"
                className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
              />
              <button className="px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg font-medium hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-300">
                Subscribe
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}